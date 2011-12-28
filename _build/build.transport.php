<?php
/**
 * mChimpX Build Script
 *
 * Copyright 2011-2012 Bert Oost <bert@oostdesign.nl>
 *
 * mChimpX is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * mChimpX is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * mChimpX; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package mchimpx
 * @subpackage build
 */
/**
 * Build mChimpX Package
 *
 * Description: Build script for mChimpX package
 * @package mchimpx
 * @subpackage build
 */

/* Set package info be sure to set all of these */
define('PKG_NAME','mChimpX');
define('PKG_NAME_LOWER', strtolower(PKG_NAME));
define('PKG_VERSION','1.0.0');
define('PKG_RELEASE','rc1');
define('PKG_CATEGORY','mChimpX');

/* set start time */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/* define sources */
$root = dirname(dirname(__FILE__)) . '/';
$sources = array (
	'root' => $root,
	'build' => $root . '_build/',
	/* note that the next two must not have a trailing slash */
	'source_core' => $root.'core/components/'.PKG_NAME_LOWER,
	'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER,
	'resolvers' => $root . '_build/resolvers/',
	'validators'=> $root . '_build/validators/',
	'data' => $root . '_build/data/',
	'docs' => $root . 'core/components/' . PKG_NAME_LOWER . '/docs/',
	'install_options' => $root . '_build/install.options/',
	'packages'=> $root . 'core/packages',
);
unset($root);

/**
 * Instantiate MODx -- if this require fails, check your
 * _build/build.config.php file
 */
require_once $sources['build'].'build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

/* load builder */
$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER,false,true,'{core_path}components/'.PKG_NAME_LOWER.'/');

/**
 * Create category  The category is required and will automatically
 * have the name of your package
 */
$category= $modx->newObject('modCategory');
$category->set('id', 1);
$category->set('category', PKG_CATEGORY);

/* add snippets */
$modx->log(modX::LOG_LEVEL_INFO,'Adding in snippets.');
$snippets = include $sources['data'].'transport.snippets.php';
if (is_array($snippets)) {
	$category->addMany($snippets, 'Snippets');
} else { $modx->log(modX::LOG_LEVEL_FATAL, 'Adding snippets failed.'); }

/* add chunks */
$modx->log(modX::LOG_LEVEL_INFO,'Adding in chunks.');
$chunks = include $sources['data'].'transport.chunks.php';
if (is_array($chunks)) {
	$category->addMany($chunks, 'Chunks');
} else { $modx->log(modX::LOG_LEVEL_FATAL, 'Adding chunks failed.'); }

/**
 * Create Category attributes array dynamically
 * based on which elements are present
 */
$attr = array(xPDOTransport::UNIQUE_KEY => 'category',
	xPDOTransport::PRESERVE_KEYS => false,
	xPDOTransport::UPDATE_OBJECT => true,
	xPDOTransport::RELATED_OBJECTS => true,
);

// setup snippets
$attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Snippets'] = array(
	xPDOTransport::PRESERVE_KEYS => false,
	xPDOTransport::UPDATE_OBJECT => true,
	xPDOTransport::UNIQUE_KEY => 'name',
);

// setup chunks
$attr[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Chunks'] = array(
	xPDOTransport::PRESERVE_KEYS => false,
	xPDOTransport::UPDATE_OBJECT => true,
	xPDOTransport::UNIQUE_KEY => 'name',
);

/**
 * Create a vehicle for the category and all the things
 * we've added to it.
 */
$vehicle = $builder->createVehicle($category, $attr);

$modx->log(modX::LOG_LEVEL_INFO, 'Packing in the core files.');
$vehicle->resolve('file',array(
	'source' => $sources['source_core'],
	'target' => "return MODX_CORE_PATH . 'components/';",
));

/**
 * Put the category vehicle (with all the stuff we added to the
 * category) into the package 
 */
$builder->putVehicle($vehicle);

/**
 * Next-to-last step - pack in the license file, readme.txt, changelog,
 * and setup options 
 */
$builder->setPackageAttributes(array(
	'license' => file_get_contents($sources['docs'] . 'license.txt'),
	'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
	'changelog' => file_get_contents($sources['docs'] . 'changelog.txt')
));

/* Last step - zip up the package */
$builder->pack();

/* report how long it took */
$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(xPDO::LOG_LEVEL_INFO, "Package Built.");
$modx->log(xPDO::LOG_LEVEL_INFO, "Execution time: {$totalTime}");
exit();

?>